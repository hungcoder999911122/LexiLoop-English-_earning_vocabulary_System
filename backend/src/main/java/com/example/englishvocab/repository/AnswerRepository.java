package com.example.englishvocab.repository;

import com.example.englishvocab.entity.Answer;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface AnswerRepository extends JpaRepository<Answer, Integer> {
    List<Answer> findByQuestion_QuestionID(Integer questionId);
}
