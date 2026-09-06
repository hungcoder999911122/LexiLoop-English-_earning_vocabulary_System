package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Vocabulary")
public class Vocabulary {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer vocabularyID;

    @ManyToOne(optional = false, fetch = FetchType.LAZY)
    @JoinColumn(name = "topicID")
    private Topic topic;

    @Column(nullable = false, length = 100)
    private String word;

    private String pronunciation;

    @Column(name = "audio_url")
    private String audioUrl;

    @Column(nullable = false)
    private String difficulty = "EASY";
}
